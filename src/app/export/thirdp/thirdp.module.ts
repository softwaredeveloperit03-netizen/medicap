import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { CustomerComponent } from './customer/customer.component';
import { OrderComponent } from './order/order.component';
import { OrderReviewComponent } from './order-review/order-review.component';
import { ConfirmationComponent } from './confirmation/confirmation.component';
import { LogComponent } from './log/log.component';
import { StatusComponent } from './status/status.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'status', component: StatusComponent},
  { path: 'log', component: LogComponent},
  { path: 'confirmation', component: ConfirmationComponent},
  { path: 'order-review', component: OrderReviewComponent},
  { path: 'order-receiving', component: OrderComponent},
  { path: 'customer', component: CustomerComponent},
 

];

@NgModule({
  declarations: [
    DashboardComponent,
    StatusComponent,
    LogComponent,
    ConfirmationComponent,
    OrderReviewComponent,
    OrderComponent,
    CustomerComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})

export class ThirdpModule { }
