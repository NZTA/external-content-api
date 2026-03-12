import { graphql } from 'react-apollo';
import gql from 'graphql-tag';

const mutation = gql`
mutation revertExternalContentToVersion($id:ID!, $toVersion:Int!) {
  rollbackExternalContent(
    ID: $id
    ToVersion: $toVersion
  ) {
    ID
  }
}
`;

const config = {
    props: ({ mutate, ownProps: { actions } }) => {
        const revertToVersion = (id, toVersion) => mutate({
            variables: {
                id,
                toVersion,
            },
        });

        return {
            actions: {
                ...actions,
                revertToVersion,
            },
        };
    },
    options: {
        // Refetch versions after mutation is completed
        refetchQueries: ['ReadHistoryViewerExternalContent']
    }
};

export { mutation, config };

export default graphql(mutation, config);
