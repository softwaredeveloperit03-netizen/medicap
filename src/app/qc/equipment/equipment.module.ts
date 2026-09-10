import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ChallanComponent } from './challan/challan.component';
import { RecvingComponent } from './recving/recving.component';
import { StockComponent } from './stock/stock.component';
import { GenComponent } from './gen/gen.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { NewComponent } from './challan/new/new.component';
import { WehingComponent } from './wehing/wehing.component';
import { TranslateModule } from '@ngx-translate/core';


const routes:Routes = [
  {path:'',component:DashboardComponent},
  {path:'challan',component:ChallanComponent},
  {path:'gen',component:GenComponent},
  {path:'recving',component:RecvingComponent},
  {path:'stock',component:StockComponent},
  {path:'new',component:StockComponent},
];



@NgModule({
  declarations: [DashboardComponent, ChallanComponent,GenComponent,RecvingComponent,StockComponent, NewComponent, WehingComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class EquipmentModule { }
