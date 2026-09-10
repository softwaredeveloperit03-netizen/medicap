import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PendingDocumentComponent } from './pending-document.component';

describe('PendingDocumentComponent', () => {
  let component: PendingDocumentComponent;
  let fixture: ComponentFixture<PendingDocumentComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PendingDocumentComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PendingDocumentComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
