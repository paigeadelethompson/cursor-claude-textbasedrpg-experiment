import { gql } from '@apollo/client';

export const GET_STOCKS = gql`
  query GetStocks {
    stocks {
      id
      symbol
      name
      currentPrice
      priceHistory {
        price
        timestamp
      }
    }
  }
`;

export const GET_MARKET_LISTINGS = gql`
  query GetMarketListings($filter: MarketListingFilter) {
    marketListings(filter: $filter) {
      id
      seller {
        id
        username
      }
      item {
        id
        name
        type
        msrp
        description
        effects {
          health
          energy
          happiness
          strength
          defense
          speed
          dexterity
        }
      }
      quantity
      price
      createdAt
    }
  }
`;

export const GET_PLAYER_STOCKS = gql`
  query GetPlayerStocks($playerId: ID!) {
    player(id: $playerId) {
      stocks {
        stock {
          id
          symbol
          name
          currentPrice
        }
        quantity
        purchasePrice
        purchaseDate
      }
    }
  }
`;

export const GET_PLAYER_CDS = gql`
  query GetPlayerCDs($playerId: ID!) {
    player(id: $playerId) {
      cds {
        id
        amount
        interestRate
        startDate
        maturityDate
        isMatured
      }
    }
  }
`; 