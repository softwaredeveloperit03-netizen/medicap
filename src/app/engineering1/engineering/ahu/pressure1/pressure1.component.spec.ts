import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Pressure1Component } from './pressure1.component';

describe('Pressure1Component', () => {
  let component: Pressure1Component;
  let fixture: ComponentFixture<Pressure1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Pressure1Component ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(Pressure1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
