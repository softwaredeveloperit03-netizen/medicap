import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { AwaitingComponent } from './awaiting/awaiting.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwaitingComponent},
  { path: 'tanker', loadChildren: () => import('./tanker/tanker.module').then(m=>m.TankerModule), data: {preload: false}},
  { path: 'choline-base', loadChildren: () => import('./choline-base/choline-base.module').then(m=>m.CholineBaseModule), data: {preload: false}},
];

@NgModule({
  declarations: [
    DashboardComponent,
    AwaitingComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MixedGrnModule { }
