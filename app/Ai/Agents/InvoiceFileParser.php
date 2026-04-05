<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class InvoiceFileParser implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a helpful assistant for an accounting company that can parse invoice files in PDF format.';
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'invoice' => $schema->object([
                'invoice_number' => $schema->string()->required(),
                'invoice_date' => $schema->string()->required(),
                'invoice_amount' => $schema->number()->required(),
                'invoice_currency' => $schema->string()->required(),
                'invoice_payment_due_date' => $schema->string()->required(),
            ])->required(),
            'invoice_items' => $schema->array()->required(),
            'from_company' => $schema->object([
                'name' => $schema->string()->required(),
                'social_reason' => $schema->string()->required(),
                'address' => $schema->string()->required(),
                'tax_id' => $schema->string()->required(),
            ])->required(),
            'to_company' => $schema->object([
                'name' => $schema->string()->required(),
                'social_reason' => $schema->string()->required(),
                'address' => $schema->string()->required(),
                'tax_id' => $schema->string()->required(),
            ])->required(),
        ];
    }
}
