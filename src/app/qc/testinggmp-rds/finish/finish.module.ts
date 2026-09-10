import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ArComponent } from './ar/ar.component';
import { CoaComponent } from './coa/coa.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AllocationComponent } from './allocation/allocation.component';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { Awaiting1Component } from './awaiting1/awaiting1.component';
import { DocsIconsModule } from '../../../floating-docs-popup/docs-icons.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
 {path:'',component:DashboardComponent},
 {path:'allocation',component:AllocationComponent},
 {path:'ar',component:ArComponent},
 {path:'coa',component:CoaComponent},
 {path:'new',component:NewComponent},
 {path:'awaiting',component:Awaiting1Component},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, ArComponent, CoaComponent, AllocationComponent, AwaitingComponent, Awaiting1Component],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    RouterModule.forChild(routes)
  ]
})
export class FinishModule { }
