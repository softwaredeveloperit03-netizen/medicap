import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Closing1Component } from './closing1.component';

describe('Closing1Component', () => {
  let component: Closing1Component;
  let fixture: ComponentFixture<Closing1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Closing1Component ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(Closing1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
