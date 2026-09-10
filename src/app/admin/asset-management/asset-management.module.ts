import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';

import { DashboardComponent } from './dashboard/dashboard.component';
import { NewAssetComponent } from './new-asset/new-asset.component';
import { RecAssestsComponent } from './rec-assests/rec-assests.component';
import { MainantanceComponent } from './mainantance/mainantance.component';
import { MaintenenceHistoryComponent } from './maintenence-history/maintenence-history.component';
import { ScrapLogComponent } from './scrap-log/scrap-log.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'newAsset', component: NewAssetComponent},
  { path: 'recordAsset', component: RecAssestsComponent},
  { path: 'assetMainantence', component: MainantanceComponent},
  { path: 'mainannteceHistory', component: MaintenenceHistoryComponent},
  { path: 'scrapLog', component: ScrapLogComponent},
   
];
 

@NgModule({
  declarations: [
    DashboardComponent,
    NewAssetComponent,
    RecAssestsComponent,
    MainantanceComponent,
    MaintenenceHistoryComponent,
    ScrapLogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
        CommonModule,
        FormsModule,
        ClarityModule,
        RouterModule.forChild(routes)
  ]
})
export class AssetManagementModule { }
