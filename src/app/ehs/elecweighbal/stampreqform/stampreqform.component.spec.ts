import { ComponentFixture, TestBed } from '@angular/core/testing';

import { StampreqformComponent } from './stampreqform.component';

describe('StampreqformComponent', () => {
  let component: StampreqformComponent;
  let fixture: ComponentFixture<StampreqformComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ StampreqformComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(StampreqformComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
