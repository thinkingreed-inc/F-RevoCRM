<?php

class CustomView_GetFavorites_Api extends Vtiger_Api_Controller
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
        if ($sourceModule === '') {
            $this->sendError('sourceModule is required', 400);
        }

        return $this->sendSuccess([
            'favorites' => CustomView_Favorite_Model::getFavoriteList($sourceModule),
        ]);
    }
}
