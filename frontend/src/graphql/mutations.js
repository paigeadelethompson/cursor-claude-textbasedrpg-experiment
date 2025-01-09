import { gql } from '@apollo/client';

export const BUY_STOCK = gql`
  mutation BuyStock($input: StockTransactionInput!) {
    buyStock(input: $input) {
      success
      stock {
        symbol
        currentPrice
      }
      quantity
      totalCost
    }
  }
`;

export const SELL_STOCK = gql`
  mutation SellStock($input: StockTransactionInput!) {
    sellStock(input: $input) {
      success
      stock {
        symbol
        currentPrice
      }
      quantity
      totalValue
    }
  }
`;

export const CREATE_MARKET_LISTING = gql`
  mutation CreateMarketListing($input: CreateListingInput!) {
    createMarketListing(input: $input) {
      id
      price
      quantity
      item {
        name
      }
    }
  }
`;

export const BUY_MARKET_LISTING = gql`
  mutation BuyMarketListing($id: ID!) {
    buyMarketListing(id: $id) {
      success
      cost
      quantity
    }
  }
`;

export const CREATE_CD = gql`
  mutation CreateCD($input: CreateCDInput!) {
    createCD(input: $input) {
      id
      amount
      interestRate
      maturityDate
    }
  }
`;

export const WITHDRAW_CD = gql`
  mutation WithdrawCD($id: ID!) {
    withdrawCD(id: $id) {
      success
      amount
      interestEarned
      totalReturn
    }
  }
`; 