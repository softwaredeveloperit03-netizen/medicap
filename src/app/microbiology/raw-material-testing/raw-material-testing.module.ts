import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AllocationComponent } from './allocation/allocation.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { CheckingComponent } from './checking/checking.component';
import { ArComponent } from './ar/ar.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'allocation', component: AllocationComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'ar', component: ArComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,
    AllocationComponent,
    AwaitingComponent,
    CheckingComponent,
    ArComponent
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
export class RawMaterialTestingModule { }
