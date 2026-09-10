import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { BaltolaranceComponent } from './baltolarance/baltolarance.component';
import { PerfchkrecordComponent } from './perfchkrecord/perfchkrecord.component'
import { DashboardComponent } from './dashboard/dashboard.component';
import { MonthlyverifComponent } from './monthlyverif/monthlyverif.component';
import { EccentricitychkComponent } from './eccentricitychk/eccentricitychk.component';
import { StampreqformComponent } from './stampreqform/stampreqform.component';
import { CaliblabelComponent } from './caliblabel/caliblabel.component';
import { DailyverifComponent } from './dailyverif/dailyverif.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path: 'baltolarance', component: BaltolaranceComponent},
  {path: 'perfchkrecord', component: PerfchkrecordComponent},
  {path: 'monthlyverif', component: MonthlyverifComponent},
  {path: 'eccentricitychk', component: EccentricitychkComponent },
  {path: 'stampreqform', component: StampreqformComponent },
  {path: 'caliblabel', component: CaliblabelComponent},
  {path: 'dailyverif', component:DailyverifComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    BaltolaranceComponent,
    PerfchkrecordComponent,
    MonthlyverifComponent,
    EccentricitychkComponent,
    StampreqformComponent,
    CaliblabelComponent,
    DailyverifComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class ElecweighbalModule { }
