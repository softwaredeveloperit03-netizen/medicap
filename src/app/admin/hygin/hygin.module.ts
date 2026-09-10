import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { PersonalHygieneComponent } from './personal-hygiene/personal-hygiene.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DropdownModule } from 'primeng/dropdown';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MultiSelectModule } from 'primeng/multiselect';
import { FactoryHygieneComponent } from './factory-hygiene/factory-hygiene.component';
import { ToiletCleaningComponent } from './toilet-cleaning/toilet-cleaning.component';
import { EtpSheetComponent } from './etp-sheet/etp-sheet.component';
import { RoHygieneComponent } from './ro-hygiene/ro-hygiene.component';
import { ScrapComponent } from './scrap/scrap.component';

import { PestComponent } from './pest/pest.component';
import { PersonalhyComponent } from './personalhy/personalhy.component';
import { HygieneReportsComponent } from './hygiene-reports/hygiene-reports.component';
import { WasteComponent } from './waste/waste.component';
import { TranslateModule } from '@ngx-translate/core';

 
const routes: Routes = [
  { path: '', component:  DashboardComponent},
  { path: 'personal-hygine', component:  PersonalHygieneComponent},
  { path: 'factory-hygiene', component:  FactoryHygieneComponent},
  { path: 'toilet-hygiene', component:  ToiletCleaningComponent},
  { path: 'etp', component:  EtpSheetComponent},
  { path: 'ro-hygiene', component:  RoHygieneComponent},
  { path: 'scrap', component:  ScrapComponent},
    { path: 'pest', component:   PestComponent},
    { path: 'hygiene-reports', component:   HygieneReportsComponent},
    { path: 'personalhy', component:   PersonalhyComponent},
    { path: 'wastedisp', component:   WasteComponent},
]

@NgModule({

  declarations: [
    PersonalHygieneComponent,
    DashboardComponent,
    FactoryHygieneComponent,
    ToiletCleaningComponent,
    EtpSheetComponent,
    ScrapComponent,
    RoHygieneComponent,
     ScrapComponent,
     PestComponent,
     PersonalhyComponent,
     HygieneReportsComponent,
     WasteComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes),
  ]
})

export class HyginModule { }
