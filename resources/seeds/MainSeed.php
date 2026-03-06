<?php

use Budgetcontrol\ApplicationTests\Seeds\SeedInterface;
use Budgetcontrol\Library\Model\Debit;
use Budgetcontrol\Seeds\Resources\Seeds\DebitSeeds;
use Phinx\Seed\AbstractSeed;
use Budgetcontrol\Library\Entity\Entry;
use Budgetcontrol\Library\Model\Expense;
use Budgetcontrol\Library\Model\Income;
use Budgetcontrol\Library\Model\Model;
use Budgetcontrol\Library\Model\Transfer;
use Budgetcontrol\Seeds\Resources\Seeds\ExpenseSeeds;
use Budgetcontrol\Seeds\Resources\Seeds\IncomeSeeds;
use Budgetcontrol\Seeds\Resources\Seeds\ModelsSeed;
use Budgetcontrol\Seeds\Resources\Seeds\SavingSeeds;
use Budgetcontrol\Seeds\Resources\Seeds\TransferSeeds;
use Budgetcontrol\Library\Model\Saving;

class MainSeed extends AbstractSeed implements SeedInterface
{

    public function run(): void
    {
        $dateTime = new DateTime();

        DebitSeeds::create(
            Debit::class,
            [
                "amount" => rand(1, 1000),
                "note" => "test",
                "category_id" => 55,
                "account_id" => 4,
                "currency_id" => 1,
                "payment_type_id" => 1,
                "end_date_time" =>  $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
                "date_time" =>  $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
                "label" => [],
                "waranty" => 1,
                "confirmed" => 1,
                'type' => Entry::debit->value,
                'workspace_id' => 1,
                'account_id' => 1,
                'payee_id' => 1,
                'uuid' => "2b598724-4766-4bec-9529-da3196533d22",
            ],
        );

        ExpenseSeeds::create(Expense::class, [
            "amount" => -500,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'uuid' => "2b598724-4766-4bec-9529-da3196533d11",
            'type' => Entry::expenses->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        IncomeSeeds::create(Income::class, [
            "amount" => 500,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'planned' => 0,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-0242ac130003",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        IncomeSeeds::create(Income::class, [
            "amount" => rand(1, 1000),
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-delete",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        IncomeSeeds::create(Income::class, [
            "amount" => 200,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            "planned" => 0,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-0242ac130005",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        IncomeSeeds::create(Income::class, [
            "amount" => 400,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-0242ac130006",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
            'planned' => 0,
        ]);

        TransferSeeds::create(Transfer::class, [
            "amount" => -300,
            "note" => "test",
            "category_id" => 75,
            "account_id" => 1,
            "currency_id" => 1,
            "transfer_id" => 2,
            "payment_type_id" => 1,
            "end_date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'type' => Entry::transfer->value,
            'workspace_id' => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'transfer_relation' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130004',
            'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903',
        ]);

        //the relation
        TransferSeeds::create(Transfer::class, [
            "amount" => 300,
            "note" => "test",
            "category_id" => 75,
            "account_id" => 2,
            "transfer_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "end_date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'type' => Entry::transfer->value,
            'workspace_id' => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'transfer_relation' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139903',
            'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130004',
        ]);

        TransferSeeds::create(Transfer::class, [
            "amount" => -300,
            "note" => "test",
            "category_id" => 75,
            "account_id" => 1,
            "currency_id" => 1,
            "transfer_id" => 2,
            "payment_type_id" => 1,
            "end_date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'type' => Entry::transfer->value,
            'workspace_id' => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'transfer_relation' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130001',
            'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139902',
        ]);

        //the relation
        TransferSeeds::create(Transfer::class, [
            "amount" => 300,
            "note" => "test",
            "category_id" => 75,
            "account_id" => 2,
            "transfer_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "end_date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "date_time" => $dateTime->modify("+20 days")->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'type' => Entry::transfer->value,
            'workspace_id' => 1,
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'transfer_relation' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac139902',
            'uuid' => 'f7b3b3b0-0b7b-11ec-82a8-0242ac130001',
        ]);

        ModelsSeed::create(Model::class, [
            "amount" => 300,
            "name" => "test",
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'uuid' => "f7b3b3b0-0b7b-11ec-82a8-0242ac130002",
            'type' => Entry::incoming->value,
            'workspace_id' => 1,
            'account_id' => 1,
        ]);

        SavingSeeds::create(Saving::class, [
            "amount" => 400,
            "note" => "test",
            "category_id" => 12,
            "account_id" => 1,
            "currency_id" => 1,
            "payment_type_id" => 1,
            "date_time" => $dateTime->format('Y-m-d H:i:s'),
            "label" => [],
            "waranty" => 1,
            "confirmed" => 1,
            'uuid' => "1b7674a9-49ab-418c-a84c-c24ae48ecbbc",
            'type' => Entry::saving->value,
            'workspace_id' => 1,
            'account_id' => 1,
            'planned' => 0,
            'goal_id' => 1
        ]);

    }

    public function getName(): string
    {
        return __CLASS__;
    }

    public function getDescription(): string
    {
        return 'Main seeds for the application, including entries, transfers, models, and savings.';
    }

    public function shouldRun(): bool
    {
        return true;
    }
}
