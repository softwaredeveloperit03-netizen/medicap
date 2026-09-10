import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AllocationComponent } from './allocation/allocation.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { AreaComponent } from './area/area.component';
import { LineComponent } from './line/line.component';
import { SampleComponent } from './sample/sample.component';
import { FinprodregComponent } from './finprodreg/finprodreg.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path: 'allocation', component: AllocationComponent},
  {path: 'new', component: NewComponent},
  {path: 'log', component: LogComponent},
  {path:'area', component:AreaComponent},
  {path:'line',component:LineComponent},
  {path:'sample',component:SampleComponent},
   { path: 'finprodreg', component: FinprodregComponent },
];

@NgModule({
  declarations: [DashboardComponent, AllocationComponent,FinprodregComponent, NewComponent, LogComponent, AreaComponent, LineComponent, SampleComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FinishModule { }
