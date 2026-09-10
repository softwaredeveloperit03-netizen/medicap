import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { BufferComponent } from './buffer/buffer.component';
import { NewComponent } from './new/new.component';
import { InssuranceComponent } from './inssurance/inssurance.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { FormComponent } from './form/form.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'buffer', component: BufferComponent},
  { path: 'new', component: NewComponent},
  { path: 'form', component: FormComponent},
  { path: 'inssurance', component: InssuranceComponent},
];
@NgModule({
  declarations: [
    BufferComponent,
    NewComponent,
    InssuranceComponent,
    DashboardComponent,
    FormComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ReadymadeModule { }
