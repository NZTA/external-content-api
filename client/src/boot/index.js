/* global window */
import Injector from 'lib/Injector';
import readOneExternalContentQuery from 'state/history/readOneExternalContentQuery';
import revertToExternalContentVersionMutation from 'state/history/revertToExternalContentVersionMutation';

window.document.addEventListener('DOMContentLoaded', () => {
    // Register GraphQL operations with Injector as transformations
    Injector.transform(
        'externalcontent-history', (updater) => {
            updater.component(
                'HistoryViewer.Form_ItemEditForm',
                readOneExternalContentQuery, 'ElementHistoryViewer');
        }
    );

    Injector.transform(
        'externalcontent-history-revert', (updater) => {
            updater.component(
                'HistoryViewerToolbar.VersionedAdmin.HistoryViewer.ExternalContent.HistoryViewerVersionDetail',
                revertToExternalContentVersionMutation,
                'ExternalContentRevertMutation'
            );
        }
    );
});
