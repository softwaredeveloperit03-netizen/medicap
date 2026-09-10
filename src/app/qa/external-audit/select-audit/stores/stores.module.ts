import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ChallanComponent } from './challan/challan.component';
import { RawGrnComponent } from './raw-grn/raw-grn.component';
import { RawRecievingComponent } from './raw-recieving/raw-recieving.component';
import { RawWeighingComponent } from './raw-weighing/raw-weighing.component';
import { RawDamageComponent } from './raw-damage/raw-damage.component';
import { PackingGrnComponent } from './packing-grn/packing-grn.component';
import { PackingRecievingComponent } from './packing-recieving/packing-recieving.component';
import { PackingWeighingComponent } from './packing-weighing/packing-weighing.component';
import { RackComponent } from './rack/rack.component';
import { RlafComponent } from './rlaf/rlaf.component';
import { UsageComponent } from './usage/usage.component';
import { UtensilsComponent } from './utensils/utensils.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { AhuComponent } from './ahu/ahu.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'challan', component: ChallanComponent},
  { path: 'raw-damage', component: RawDamageComponent},
  { path: 'raw-grn', component: RawGrnComponent},
  { path: 'raw-recieving', component: RawRecievingComponent},
  { path: 'raw-weighing', component: RawWeighingComponent},
  { path: 'packing-grn', component: PackingGrnComponent},
  { path: 'packing-recieving', component: PackingRecievingComponent},
  { path: 'packing-weighing', component: PackingWeighingComponent},
  { path: 'rack', component: RackComponent},
  { path: 'rlaf', component: RlafComponent},
  { path: 'usage', component: UsageComponent},
  { path: 'utensils', component: UtensilsComponent},
  { path: 'ahu', component: AhuComponent},



];

@NgModule({
  declarations: [
    DashboardComponent,
    ChallanComponent,
    RawGrnComponent,
    RawRecievingComponent,
    RawWeighingComponent,
    RawDamageComponent,
    AhuComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)  ]
})
export class StoresModule { }
