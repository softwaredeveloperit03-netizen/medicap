import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DossierreqComponent } from './dossierreq/dossierreq.component';
import { DossiernewComponent } from './dossiernew/dossiernew.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { FormsModule,ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},

  { path: 'dossierreqform', component: DossierreqComponent},
  { path: 'dossierNew', component: DossiernewComponent},

 
];

@NgModule({
  declarations: [
    DossierreqComponent,
    DossiernewComponent,
    DashboardComponent

  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    RouterModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    FormsModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)




  ],
  providers:[]
})
export class DossierRequestModule { }
