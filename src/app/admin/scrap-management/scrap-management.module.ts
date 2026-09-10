import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router'; 
import { ListVendorComponent } from './list-vendor/list-vendor.component';
import { AddVendorComponent} from './add-vendor/add-vendor.component';
import { ListScrapMaterialComponent } from './list-scrap-material/list-scrap-material.component';
import { AddScrapMaterialComponent } from './add-scrap-material/add-scrap-material.component';
import { ScrapListComponent } from './scrap-list/scrap-list.component';
import { ScrapEntryComponent } from './scrap-entry/scrap-entry.component';
import { ScrapSalesComponent } from './scrap-sales/scrap-sales.component';
import { ScrapReportComponent } from './scrap-report/scrap-report.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'list-vendor', component:ListVendorComponent },
  { path: 'add-vendor', component:AddVendorComponent },
  { path: 'list-material', component:ListScrapMaterialComponent },
  { path: 'add-material', component:AddScrapMaterialComponent },
  { path: 'scrap-list', component:ScrapListComponent },
  { path: 'scrap-entry', component:ScrapEntryComponent },
  { path: 'scrap-sales', component:ScrapSalesComponent },
  { path: 'scrap-report', component:ScrapReportComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    ListVendorComponent,
    AddVendorComponent,
    ListScrapMaterialComponent,
    AddScrapMaterialComponent,
    ScrapListComponent,
    ScrapEntryComponent,
    ScrapSalesComponent,
    ScrapReportComponent, 
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
}) 

export class ScrapManagementModule { }
