import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RawComponent } from './raw/raw.component';
import { PackingComponent } from './packing/packing.component';
import { StationaryComponent } from './stationary/stationary.component';
import { ChemicalsComponent } from './chemicals/chemicals.component';
import { EquipmentsComponent } from './equipments/equipments.component';
import { GlasswareComponent } from './glassware/glassware.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { GstoreComponent } from './gstore/gstore.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'raw', component: RawComponent},
  { path: 'packing', component: PackingComponent},
  { path: 'stationary', component: StationaryComponent},
  { path: 'chemicals', component: ChemicalsComponent},
  { path: 'glasswares', component:GlasswareComponent},
  { path: 'gstore', component:GstoreComponent}
 ];

@NgModule({
  declarations: [DashboardComponent, RawComponent, PackingComponent, StationaryComponent, ChemicalsComponent, EquipmentsComponent, GlasswareComponent, GstoreComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class InventoryModule { }
