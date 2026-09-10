import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { DiscardingComponent } from './discarding/discarding.component';
import { SterilizationComponent } from './sterilization/sterilization.component';
import { TranslateModule } from '@ngx-translate/core';


const routes : Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'sterilization', component: SterilizationComponent},
  { path: 'discarding', component: DiscardingComponent}
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, DiscardingComponent, SterilizationComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class AutocleavingModule { }
