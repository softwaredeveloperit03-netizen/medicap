import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ListComponent } from './list/list.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  {path: '', component: DashboardComponent},
  {path:'list',component:ListComponent},
  {path:'test', loadChildren: () => import('./test/test.module').then(m=>m.TestModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    DashboardComponent,
    ListComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EarthingModule { }
