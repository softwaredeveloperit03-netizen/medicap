import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule, ReactiveFormsModule,} from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'ahu', loadChildren: () => import('./ahu/ahu.module').then(m=>m.AhuModule), data: {preload: false}},
  { path: 'ventfilter', loadChildren: () => import('./ventfilter/ventfilter.module').then(m=>m.VentfilterModule), data: {preload: false}},

];
@NgModule({
  declarations: [ DashboardComponent ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class FilterManagementModule { }
