import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MapComponent } from './map/map.component';
import { ReportComponent } from './report/report.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: 'map', component: MapComponent },
  { path: 'report', component: ReportComponent },
  { path: 'log', component: LogComponent },
  { path: 'new', component: NewComponent },
  { path: '', pathMatch: 'full', component: DashboardComponent },
];

@NgModule({
  declarations: [
    MapComponent,
    ReportComponent,
    DashboardComponent,
    NewComponent,
    LogComponent
  

 
    
  ],
  imports: [
    SharedModule, TranslateModule,

    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes)

  ]
})
export class MaterialModule { }
