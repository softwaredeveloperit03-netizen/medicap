import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TechnicalDocumentRoutingModule } from './technical-document-routing.module';
import { TechnicalDocumentComponent } from './technical-document/technical-document.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { HttpClientModule } from '@angular/common/http';
import { DocumentComponent } from './document/document.component';
import { TranslateModule } from '@ngx-translate/core';


@NgModule({
  declarations: [

    TechnicalDocumentComponent,
    DocumentComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    TechnicalDocumentRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    HttpClientModule,
  ]
})
export class TechnicalDocumentModule { }
