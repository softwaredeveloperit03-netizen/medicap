import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Verification1Component } from './verification1.component';

describe('Verification1Component', () => {
  let component: Verification1Component;
  let fixture: ComponentFixture<Verification1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Verification1Component ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(Verification1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
