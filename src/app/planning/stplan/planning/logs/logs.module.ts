import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReceivedComponent } from './received/received.component';
import { RejectedComponent } from './rejected/rejected.component';
import { HoldComponent } from './hold/hold.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';

const routes: Routes = [
  { path: 'received', component: ReceivedComponent},
  { path: 'rejected', component: RejectedComponent},
  { path: 'onhold', component: HoldComponent},
 ];

@NgModule({
  declarations: [
    ReceivedComponent,
    RejectedComponent,
    HoldComponent
  ],
  imports: [
       CommonModule,
       FormsModule,
       ClarityModule,
       RouterModule.forChild(routes)
  ]
})
export class LogsModule { }
