import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DailyWorkComponent } from './daily-work/daily-work.component';
import { CleaningLogComponent } from './cleaning-log/cleaning-log.component';
import { ResourcesComponent } from './resources/resources.component';
import { PlantCountComponent } from './plant-count/plant-count.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'cleaning-log', component: CleaningLogComponent},
  { path: 'daily-work', component: DailyWorkComponent},
  { path: 'plant-count', component: PlantCountComponent},
  { path: 'resources', component: ResourcesComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    DailyWorkComponent,
    CleaningLogComponent,
    ResourcesComponent,
    PlantCountComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class GardevModule { }
