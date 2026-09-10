import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TicketsLogComponent } from './tickets-log/tickets-log.component';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: TicketsLogComponent},
];

@NgModule({
  declarations: [
    TicketsLogComponent,
  ],
  imports: [ TranslateModule,
    CommonModule,
    RouterModule.forChild(routes)  
  ]
})
export class AgentModule { }
