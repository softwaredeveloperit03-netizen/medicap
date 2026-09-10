import { NgModule } from '@angular/core';
import { CommonModule, DatePipe } from '@angular/common';

import { PackingRoutingModule } from './packing-routing.module';
import { HomeComponent } from './home/home.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ReportComponent } from './report/report.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [HomeComponent, NewComponent, ReportComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    PackingRoutingModule,
    FormsModule,
    ClarityModule
  ],
  providers: [DatePipe]
})
export class PackingModule { }
