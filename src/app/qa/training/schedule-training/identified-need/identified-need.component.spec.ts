import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IdentifiedNeedComponent } from './identified-need.component';

describe('IdentifiedNeedComponent', () => {
  let component: IdentifiedNeedComponent;
  let fixture: ComponentFixture<IdentifiedNeedComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IdentifiedNeedComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(IdentifiedNeedComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
