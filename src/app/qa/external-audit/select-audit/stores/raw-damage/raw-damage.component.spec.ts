import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawDamageComponent } from './raw-damage.component';

describe('RawDamageComponent', () => {
  let component: RawDamageComponent;
  let fixture: ComponentFixture<RawDamageComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawDamageComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(RawDamageComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
