import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { EquipInstFormComponent } from './equip-inst-form/equip-inst-form.component';

import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DatabackuplogComponent } from './databackuplog/databackuplog.component';
import { SafedepositlogComponent } from './safedepositlog/safedepositlog.component';
import { DataretriveComponent } from './dataretrive/dataretrive.component';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'equip-inst-form', component: EquipInstFormComponent },
  { path: 'databackuplog', component: DatabackuplogComponent },
  { path: 'safedepositlog', component: SafedepositlogComponent },
  { path: 'dataretrive', component: DataretriveComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    EquipInstFormComponent,
    SafedepositlogComponent,
    DatabackuplogComponent,
    SafedepositlogComponent,
    DataretriveComponent
  ],
  imports: [
    SharedModule,
   CommonModule,
       FormsModule,
         ClarityModule,
         TranslateModule,
         RouterModule.forChild(routes)
  ]
})
export class BackuprestoreModule { }
