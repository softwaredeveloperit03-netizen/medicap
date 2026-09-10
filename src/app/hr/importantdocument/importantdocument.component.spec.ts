import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImportantdocumentComponent } from './importantdocument.component';

describe('ImportantdocumentComponent', () => {
  let component: ImportantdocumentComponent;
  let fixture: ComponentFixture<ImportantdocumentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ImportantdocumentComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ImportantdocumentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
