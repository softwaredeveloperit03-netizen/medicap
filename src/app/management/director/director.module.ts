import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { FoapprovalComponent } from './foapproval/foapproval.component';
import { PoapprovalComponent } from './poapproval/poapproval.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'foapproval', component: FoapprovalComponent},
  { path: 'poapproval', component: PoapprovalComponent},
  { path: 'indent', loadChildren: () => import('./indent/indent.module').then(m=>m.IndentModule), data: {preload: false}},


];
 

@NgModule({
  declarations: [
    DashboardComponent,
    FoapprovalComponent,
    PoapprovalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})


export class DirectorModule { }
