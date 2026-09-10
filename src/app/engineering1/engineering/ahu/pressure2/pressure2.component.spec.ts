import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Pressure2Component } from './pressure2.component';

describe('Pressure2Component', () => {
  let component: Pressure2Component;
  let fixture: ComponentFixture<Pressure2Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Pressure2Component ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(Pressure2Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
