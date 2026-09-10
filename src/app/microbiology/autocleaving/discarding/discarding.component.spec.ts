import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DiscardingComponent } from './discarding.component';

describe('DiscardingComponent', () => {
  let component: DiscardingComponent;
  let fixture: ComponentFixture<DiscardingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DiscardingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DiscardingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
