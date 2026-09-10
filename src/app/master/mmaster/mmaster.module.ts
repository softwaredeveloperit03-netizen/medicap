import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { GeneralmaterialComponent } from './generalmaterial/generalmaterial.component';
import { EnggmaterialComponent } from './enggmaterial/enggmaterial.component';
import { MfgconsuComponent } from './mfgconsu/mfgconsu.component';
import { QcconsuComponent } from './qcconsu/qcconsu.component';
import { UomComponent } from './uom/uom.component';
import { PacksizeComponent } from './packsize/packsize.component';
import { StoragecondiComponent } from './storagecondi/storagecondi.component';
import { GradeComponent } from './grade/grade.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: 'generalmat', component: GeneralmaterialComponent},
  { path: 'enggmat', component: EnggmaterialComponent},
  { path: 'uom', component: UomComponent},
  { path: 'grade', component: GradeComponent},
  { path: 'storage_condition', component: StoragecondiComponent},
  { path: 'pack_size', component: PacksizeComponent},
  { path: 'tax', component: QcconsuComponent},
  { path: 'fgtype', component: MfgconsuComponent},
  
];




@NgModule({
  declarations: [
    GeneralmaterialComponent,
    EnggmaterialComponent,
    MfgconsuComponent,
    QcconsuComponent,
    UomComponent,
    PacksizeComponent,
    StoragecondiComponent,
    GradeComponent
  ],
  imports: [
    SharedModule,
    MasterExcelModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ]
})
export class MmasterModule { }
