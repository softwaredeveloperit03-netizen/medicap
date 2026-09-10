import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashbordComponent } from './dashbord/dashbord.component';
import { NewComponent } from './new/new.component';
import { TranslateModule } from '@ngx-translate/core';




@NgModule({
  declarations: [
    DashbordComponent,
    NewComponent
  ],
  imports: [ TranslateModule,
    CommonModule
  ]
})
export class EnventoryModule { }
