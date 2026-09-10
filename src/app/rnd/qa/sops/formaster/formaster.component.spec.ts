import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FormasterComponent } from './formaster.component';

describe('FormasterComponent', () => {
  let component: FormasterComponent;
  let fixture: ComponentFixture<FormasterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FormasterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FormasterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
