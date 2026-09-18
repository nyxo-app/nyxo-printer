<?php

declare(strict_types=1);

namespace Nyxo\Printer\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Nyxo\Printer\Contracts\PrintServiceInterface;
use Nyxo\Printer\Models\PrinterNode;

class NyxoPrinterModal extends Component
{
    public bool $showModal = false;

    public ?int $documentId = null;

    public string $documentType = 'order'; // 'order' | 'receipt' | 'invoice' | 'voucher'

    public string $format = 'a4'; // 'a4' | 'ticket_80mm' | 'ticket_58mm'

    public ?int $printerNodeId = null;

    public int $copies = 1;

    public ?string $feedbackMessage = null;

    public string $feedbackType = 'success'; // 'success' | 'error'

    public bool $isSending = false;

    #[On('open-print-modal')]
    public function open(
        int $documentId,
        string $documentType = 'order',
        ?string $format = null,
        ?int $printerNodeId = null,
        int $copies = 1
    ): void {
        $this->documentId = $documentId;
        $this->documentType = $documentType;
        $this->format = $format ?? 'a4';
        $this->copies = max(1, $copies);

        // Smart preselection:
        // 1. Explicit parameter
        // 2. Recent session
        // 3. User assigned printer node
        // 4. First active node available
        if ($printerNodeId && PrinterNode::where('id', $printerNodeId)->where('is_active', true)->exists()) {
            $this->printerNodeId = $printerNodeId;
        } elseif (session()->has('nyxo_last_printer_node_id') && PrinterNode::where('id', session('nyxo_last_printer_node_id'))->where('is_active', true)->exists()) {
            $this->printerNodeId = (int) session('nyxo_last_printer_node_id');
        } else {
            $user = Auth::user();
            if ($user && isset($user->printer_node_id) && PrinterNode::where('id', $user->printer_node_id)->where('is_active', true)->exists()) {
                $this->printerNodeId = (int) $user->printer_node_id;
            } else {
                $this->printerNodeId = PrinterNode::active()->first()?->id;
            }
        }

        $this->feedbackMessage = null;
        $this->isSending = false;
        $this->showModal = true;
    }

    public function setDocumentType(string $type): void
    {
        $this->documentType = $type;
        $this->feedbackMessage = null;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
        $this->feedbackMessage = null;
    }

    public function setPrinterNode(int $nodeId): void
    {
        $this->printerNodeId = $nodeId;
        session(['nyxo_last_printer_node_id' => $nodeId]);
        $this->feedbackMessage = null;
    }

    public function sendToPrinter(PrintServiceInterface $printService): void
    {
        if (! $this->documentId || ! $this->printerNodeId) {
            $this->feedbackMessage = 'You must select an active printer node.';
            $this->feedbackType = 'error';

            return;
        }

        $this->isSending = true;

        try {
            session(['nyxo_last_printer_node_id' => $this->printerNodeId]);

            // Dispatch domain event for client application to build and enqueue the payload
            $this->dispatch('nyxo-print-requested', [
                'documentId' => $this->documentId,
                'documentType' => $this->documentType,
                'format' => $this->format,
                'printerNodeId' => $this->printerNodeId,
                'copies' => $this->copies,
            ]);

            $node = PrinterNode::find($this->printerNodeId);
            $nodeName = $node?->name ?? "Node #{$this->printerNodeId}";

            $this->feedbackMessage = "Document successfully dispatched to the print queue of '{$nodeName}'!";
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->feedbackMessage = 'Error sending to printer: '.$e->getMessage();
            $this->feedbackType = 'error';
        } finally {
            $this->isSending = false;
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->feedbackMessage = null;
    }

    public function render(): View
    {
        $printerNodes = PrinterNode::active()->orderBy('name')->get();

        return view('nyxo-printer::livewire.nyxo-printer-modal', [
            'printerNodes' => $printerNodes,
        ]);
    }
}
