import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RepairRecordComponent } from './repair-record/repair-record.component';
import { PaintWallComponent } from './paint-wall/paint-wall.component';
import { MaintenaceComponent } from './maintenace/maintenace.component';
import { MajorMaintenanceComponent } from './major-maintenance/major-maintenance.component';
import { CleaningComponent } from './cleaning/cleaning.component';
import { DisasterComponent } from './disaster/disaster.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'repair-record', component: RepairRecordComponent},
  { path: 'paint-wall', component: PaintWallComponent},
  { path: 'Emaintenance', component: MaintenaceComponent},
  { path: 'major-maintenance', component: MajorMaintenanceComponent},
  { path: 'cleaning', component: CleaningComponent},
  { path: 'disaster', component: DisasterComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    RepairRecordComponent,
    PaintWallComponent,
    MaintenaceComponent,
    MajorMaintenanceComponent,
    CleaningComponent,
    DisasterComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FavilityModule { }
