
Class Diagram :

```mermaid
classDiagram
    direction TB
    class User {
	    -int ID
	    -String name
	    -String firstName
	    -String mail
	    -String phone
	    -long walletID
	    +getID()
	    +getName()
	    +getFirstName()
	    +getMail()
	    +getPhone()
	    +getWalletID()
    }
    
    class Transaction {
	    -long ID
	    -float amount
	    -date date
	    -TransactionType type
	    +getAmount()
	    +getDate()
	    +getType()
    }

    class Wallet {
	    -long ID
	    -float sold
	    +getID()
	    +getSold()
    }

    class TransactionType {
        <<enumeration>>
        CREDIT
        DEBIT
    }

    User "1" -- "1" Wallet
    Wallet "1" -- "*" Transaction
    Transaction --> TransactionType
