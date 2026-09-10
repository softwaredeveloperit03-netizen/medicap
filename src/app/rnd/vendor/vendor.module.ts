import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprovedComponent } from './approved/approved.component';
import { ProvisionalComponent } from './provisional/provisional.component';
import { BlacklistComponent } from './blacklist/blacklist.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approved', component: ApprovedComponent},
  { path: 'provisional', component: ProvisionalComponent},
  { path: 'blacklist', component: BlacklistComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ApprovedComponent,
    ProvisionalComponent,
    BlacklistComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class VendorModule { }
