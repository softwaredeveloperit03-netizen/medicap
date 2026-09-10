import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FormNewComponent } from './form-new.component';

describe('FormNewComponent', () => {
  let component: FormNewComponent;
  let fixture: ComponentFixture<FormNewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FormNewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FormNewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
