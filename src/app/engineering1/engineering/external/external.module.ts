import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import{NewComponent}from  './new/new.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { ExternalComponent } from './external.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: 'new', component: NewComponent },
  { path: '', component: ExternalComponent },
];
 
@NgModule({
  declarations: [
    NewComponent,
    ExternalComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ExternalModule{ }
