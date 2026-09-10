import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'master', loadChildren: () => import('./master/master.module').then(m=>m.MasterModule), data: {preload: false}},
  { path: 'issued', loadChildren: () => import('./issued/issued.module').then(m=>m.IssuedModule), data: {preload: false}},

];
 
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AssetModule { }
