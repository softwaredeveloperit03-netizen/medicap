import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { FireComponent } from './fire/fire.component';
import { SignComponent } from './sign/sign.component';
import { MocComponent } from './moc/moc.component';
import { TheftComponent } from './theft/theft.component';
import { SafetytrainingComponent } from './safetytraining/safetytraining.component';
import { AccidentComponent } from './accident/accident.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'fire', component: FireComponent},
  { path: 'sign', component: SignComponent},
  { path: 'moc', component: MocComponent},
  { path: 'safetytraining', component: SafetytrainingComponent},
  { path: 'theft', component: TheftComponent},
  { path: 'accident', component: AccidentComponent},
  
  { path: 'safety', loadChildren: () => import('./safety/safety.module').then(m=>m.SafetyModule), data: {preload: false}},
  { path: 'firetraining', loadChildren: () => import('./firetraining/firetraining.module').then(m=>m.FiretrainingModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent ,FireComponent,SignComponent, MocComponent, SafetytrainingComponent,TheftComponent, AccidentComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class SafteyModule { }
  