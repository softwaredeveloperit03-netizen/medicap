import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RevisionRoutingModule } from './revision-routing.module';
import { HomeComponent } from './home/home.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [HomeComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    RevisionRoutingModule,
    FormsModule,
    ClarityModule
  ]
})
export class RevisionModule { }
