<?php

class CustomView_SaveFavorite_Api extends Vtiger_Api_Controller
{
    /**
     * @return list<array<string, string>>
     */
    public function requiresPermission(Vtiger_Request $request)
    {
        $permissions = parent::requiresPermission($request);
        $permissions[] = ['module_parameter' => 'sourceModule', 'action' => 'DetailView'];
        return $permissions;
    }

    /**
     * @return Vtiger_Response
     */
    protected function processApi(Vtiger_Request $request)
    {
        $sourceModule = (string) $request->get('sourceModule');
        $cvId = (int) $request->get('record');
        if ($sourceModule === '' || $cvId <= 0) {
            $this->sendError('sourceModule and record are required', 400);
        }

        $favorite = in_array((string) $request->get('favorite'), ['1', 'true'], true);
        $accessibleViews = null;
        if ($favorite) {
            $accessibleViews = CustomView_Favorite_Model::getAccessibleViews($sourceModule);
            if (!isset($accessibleViews[$cvId])) {
                $this->sendError(vtranslate('LBL_PERMISSION_DENIED'), 403);
            }
            CustomView_Favorite_Model::add($cvId);
        } else {
            // 外すのは自分の行だけなので、参照できなくなったリストでも外せるようにする
            CustomView_Favorite_Model::remove($cvId);
        }

        return $this->sendSuccess([
            'record' => $cvId,
            'favorite' => $favorite,
            'favorites' => CustomView_Favorite_Model::getFavoriteList($sourceModule, $accessibleViews),
        ]);
    }

    /**
     * @return void
     */
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}
