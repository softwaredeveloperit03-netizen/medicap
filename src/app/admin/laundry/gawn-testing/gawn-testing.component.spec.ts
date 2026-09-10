import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GawnTestingComponent } from './gawn-testing.component';

describe('GawnTestingComponent', () => {
  let component: GawnTestingComponent;
  let fixture: ComponentFixture<GawnTestingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ GawnTestingComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(GawnTestingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
