import { ComponentFixture, TestBed } from '@angular/core/testing';

import { UnitforComponent } from './unitfor.component';

describe('UnitforComponent', () => {
  let component: UnitforComponent;
  let fixture: ComponentFixture<UnitforComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ UnitforComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(UnitforComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
