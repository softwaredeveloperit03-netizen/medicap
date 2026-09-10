import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ExpiredComponent } from './expired/expired.component';
import { DistroyComponent } from './distroy/distroy.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ReviewComponent } from './review/review.component';
import { NewReviewComponent } from './review/new/new.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'log', component: LogComponent},
  { path: 'expired', component: ExpiredComponent},
  { path: 'distroy', component: DistroyComponent},
  { path: 'review', component: ReviewComponent},
  { path: 'new-review', component: NewReviewComponent},

  { path: 'rack', loadChildren: () => import('./rack/rack.module').then(m=>m.RackModule), data: {preload: false}},
  { path: 'withdrawal', loadChildren: () => import('./withdrawal/withdrawal.module').then(m=>m.WithdrawalModule), data: {preload: false}},
  { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'packing', loadChildren: () => import('./packing/packing.module').then(m=>m.PackingModule), data: {preload: false}},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, LogComponent, ExpiredComponent, DistroyComponent, ReviewComponent,NewReviewComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes)
  ]
})
export class ControlsampleModule { }
