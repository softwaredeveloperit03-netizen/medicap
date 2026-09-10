import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StartsampleComponent } from './startsample.component';

describe('StartsampleComponent', () => {
  let component: StartsampleComponent;
  let fixture: ComponentFixture<StartsampleComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StartsampleComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StartsampleComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
