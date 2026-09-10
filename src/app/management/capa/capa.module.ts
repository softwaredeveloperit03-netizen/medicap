import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CapaRoutingModule } from './capa-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { NewCapaComponent } from './new-capa/new-capa.component';
import { ApproveComponent } from './approve/approve.component';
import { LogbookComponent } from './logbook/logbook.component';
import { TranslateModule } from '@ngx-translate/core';


@NgModule({
  declarations: [
    DashboardComponent,
    NewCapaComponent,
    ApproveComponent,
    LogbookComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    HttpClientModule,
    CapaRoutingModule
  ],
})
export class CapaModule { }
