import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Sheduleo1Component } from './sheduleo1.component';

describe('Sheduleo1Component', () => {
  let component: Sheduleo1Component;
  let fixture: ComponentFixture<Sheduleo1Component>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ Sheduleo1Component ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Sheduleo1Component);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
